package com.esap_be.demo.Models.Entities;

import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.springframework.data.redis.core.RedisHash;

import java.util.Date;

@RedisHash("DataSet")
@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class DataSet {
    private int id;
    private String name;
    private String description;
    private Date creationDate;
}
